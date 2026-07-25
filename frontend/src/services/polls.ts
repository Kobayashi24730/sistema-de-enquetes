import api from "./api";

export async function voteInPoll(pollId: number | string, opitionId: number | string) {
    const response = await api.post(`/polls/${pollId}/vote`, {
        option_Id: opitionId
    });
    return response.data;
}

export async function createPoll(PollData: { title: string, options: string[], category?: string }) {
    const response = await api.post("/polls", PollData);
    return response.data
}